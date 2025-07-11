# Developing with Visual Studio Code + devcontainer

The easiest way to get started with development is to use Visual Studio Code with devcontainers. This approach will create a preconfigured development environment with all the tools you need. [More about devcontainers](https://code.visualstudio.com/docs/devcontainers/containers).

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/)
- [Visual Studio code](https://code.visualstudio.com/)
- [Git](https://git-scm.com/)

## Getting started

- go to [emoncms](https://github.com/emoncms/emoncms) repository and click <b>Fork</b>.
- git clone your emoncms fork on your development machine
- launch Visual Studio Code and open your emoncms folder. It should ask you to reopen in devcontainer. The dev container image will then be built (this may take a few minutes), after this your development environment will be ready.

Navigate a web browser to http://localhost:8088, and you should see the emoncms login screen.

## working on modules

You may want to work on a specific module, which you must fork to your github account. 

Adapt the url in the [.devcontainer/setup.sh](setup.sh) file.

For example, if you want to work on the graph module, you have to change : 
```
git remote set-url origin https://github.com/emoncms/graph
```
to :
```
git remote set-url origin https://github.com/your_github_username/graph
```

Open the Command Palette in Visual Studio Code, throught `Ctrl+Shift+P` and choose `Dev Containers: Rebuild Container`
    
# Submit your work

To commit your changes:
```
git add .
git commit -m "Add some feature"
git push
```

To submit a work on a specific module, just `cd` into the module directory and use the same technique.

classic modules are in `/var/www/emoncms/Modules`

symlinked modules are in `/opt/emoncms/modules`